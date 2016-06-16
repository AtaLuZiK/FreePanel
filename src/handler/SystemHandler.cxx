#include "../Handler.h"
#include "SystemHandler.h"
#include <stdio.h>
#include <sstream>
#include <rapidjson/document.h>
#include <rapidjson/writer.h>
#include <rapidjson/stringbuffer.h>
#include "../common.h"
#include "../freepaneld.h"

#define NEXT_WORD(variable, text)   \
    const char *variable = text;    \
    {   \
        char *p = const_cast<char *>(strchr(variable, ' '));  \
        if (p) {    \
            *p = 0; \
            text = const_cast<char *>(skip_space(p + 1));   \
        }   \
    }

void SystemHandler::OnRequest(HttpRequest& request, HttpResponse& response)
{
    rapidjson::Document document;
    rapidjson::Document::AllocatorType& allocator = document.GetAllocator();
    rapidjson::Value rootNode(rapidjson::kObjectType);
    // freepaneld
    rapidjson::Value freepaneldNode(rapidjson::kObjectType);
    freepaneldNode.AddMember("daemon", DAEMON_NAME, allocator);
    freepaneldNode.AddMember("version", FREEPANELD_VERSION, allocator);
    rootNode.AddMember("freepaneld", freepaneldNode, allocator);
    // paititions
    rapidjson::Value partitions(rapidjson::kArrayType);
    std::string result = exec_command("df");
    char *saveptr = nullptr;
    char *line = strtok_r(const_cast<char *>(result.c_str()), "\n", &saveptr);
    while (line = strtok_r(nullptr, "\n", &saveptr)) {  // skip first line
        rapidjson::Value partition(rapidjson::kObjectType);
        char *tmp = line;
        NEXT_WORD(device, tmp);
        if (strncmp(device, "/dev/", 5))
            continue;
        NEXT_WORD(totalSize, tmp);
        NEXT_WORD(usedSize, tmp);
        NEXT_WORD(freeSize, tmp);
        NEXT_WORD(temp, tmp);
        NEXT_WORD(mountPoint, tmp);
        rapidjson::Value deviceStr(rapidjson::kStringType);
        deviceStr.SetString(device, strlen(device));
        partition.AddMember("device", deviceStr, allocator);
        rapidjson::Value mpStr(rapidjson::kStringType);
        mpStr.SetString(mountPoint, strlen(mountPoint));
        partition.AddMember("mountPoint", mpStr, allocator);
        partition.AddMember("totalSize", atol(totalSize), allocator);
        partition.AddMember("usedSize", atol(usedSize), allocator);
        partition.AddMember("freeSize", atol(freeSize), allocator);
        partitions.PushBack(partition, allocator);
    }
    rootNode.AddMember("partitions", partitions, allocator);
    rapidjson::StringBuffer buffer;
    rapidjson::Writer<rapidjson::StringBuffer> writer(buffer);
    rootNode.Accept(writer);
    response.Write(buffer.GetString());
    response.SetContentType("application/json");
    response.SetCharacterEncoding("UTF-8");
}

